define(['jquery', 'core/log', 'qtype_aitext/wavencoder'], function ($, log, wavencoder) {
    "use strict"; // jshint ;_;
    /*
    This file is the engine that drives audio rec and canvas drawing. TT Recorder is the just the glory kid
     */

    log.debug('qtype_aitext Audio Helper initialising');

    return {
        encoder: null,
        microphone: null,
        isRecording: false,
        audioContext: null,
        processor: null,
        uniqueid: null,
        alreadyhadsound: false, //only start silence detection after we got a sound. Silence detection is end of speech.
        silencecount: 0, //how many intervals of consecutive silence so far
        silenceintervals: 15, //how many consecutive silence intervals (100ms) = silence detected
        silencelevel: 25, //below this volume level = silence

        config: {
            bufferLen: 4096,
            numChannels: 2,
            mimeType: 'audio/wav'
        },

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },


        init: function(waveHeight, uniqueid, therecorder) {

            this.waveHeight = waveHeight;
            this.uniqueid=uniqueid;
            this.therecorder= therecorder;
            this.prepare_html();


            window.AudioContext = window.AudioContext || window.webkitAudioContext;

        },

        onStop: function() {},
        onStream: function() {},
        onError: function() {},


        prepare_html: function(){
            this.canvas =$('.' + this.uniqueid + "_waveform");
            this.canvasCtx = this.canvas[0].getContext("2d");
        },

        start: function() {

            var that =this;

            // Audio context
            this.audioContext = new AudioContext();
            if (this.audioContext.createJavaScriptNode) {
                this.processor = this.audioContext.createJavaScriptNode(this.config.bufferLen, this.config.numChannels, this.config.numChannels);
            } else if (this.audioContext.createScriptProcessor) {
                this.processor = this.audioContext.createScriptProcessor(this.config.bufferLen, this.config.numChannels, this.config.numChannels);
            } else {
                log.debug('WebAudio API has no support on this browser.');
            }
            this.processor.connect(this.audioContext.destination);


            var gotStreamMethod= function(stream) {
                that.onStream(stream);
                that.isRecording = true;
                that.therecorder.update_audio('isRecording',true);
                that.tracks = stream.getTracks();

                //lets check the noise suppression and echo reduction on these
                for(var i=0; i<that.tracks.length; i++){
                    var track = that.tracks[i];
                    if(track.kind == "audio"){
                        var settings = track.getSettings();
                        if(settings.noiseSuppression){
                            log.debug("Noise Suppression is on");
                        }else{
                            log.debug("Noise Suppression is off");
                        }
                        if(settings.echoCancellation){
                            log.debug("Echo Cancellation is on");
                        }else{
                            log.debug("Echo Cancellation is off");
                        }
                    }
                }

                // Create a MediaStreamAudioSourceNode for the microphone
                that.microphone = that.audioContext.createMediaStreamSource(stream);

                // Connect the AudioBufferSourceNode to the gainNode
                that.microphone.connect(that.processor);
                that.encoder = wavencoder.clone();
                that.encoder.init(that.audioContext.sampleRate, 2);

                // Give the node a function to process audio events
                that.processor.onaudioprocess = function(event) {
                    that.encoder.encode(that.getBuffers(event));
                };

                that.listener = that.audioContext.createAnalyser();
                that.microphone.connect(that.listener);
                that.listener.fftSize = 2048; // 256

                that.bufferLength = that.listener.frequencyBinCount;
                that.analyserData = new Uint8Array(that.bufferLength);
                that.volumeData = new Uint8Array(that.bufferLength);

                //reset canvas and silence detection
                that.canvasCtx.clearRect(0, 0, that.canvas.width()*2, that.waveHeight*2);
                that.alreadyhadsound= false;
                that.silencecount= 0;

                that.interval = setInterval(function() {
                    that.drawWave();
                    that.detectSilence();
                }, 100);

            };

            //for ios we need to do this to keep playback volume high
            if ("audioSession" in navigator) {
                navigator.audioSession.type = 'play-and-record';
                console.log("AudioSession API is supported");
            }

            // Mic permission
            navigator.mediaDevices.getUserMedia({
                audio: true,
                video: false
            }).then(gotStreamMethod).catch(this.onError);
        },

        stop: function() {
            clearInterval(this.interval);
            this.canvasCtx.clearRect(0, 0, this.canvas.width()*2, this.waveHeight * 2);
            this.isRecording = false;
            this.silencecount=0;
            this.alreadyhadsound=false;
            this.therecorder.update_audio('isRecording',false);
            //we check audiocontext is not in an odd state before closing
            //superclickers can get it in an odd state
            if (this.audioContext!==null && this.audioContext.state !== "closed") {
                this.audioContext.close();
             }
            this.processor.disconnect();
            this.tracks.forEach(track => track.stop());
            this.onStop(this.encoder.finish());
        },

        getBuffers: function(event) {
            var buffers = [];
            for (var ch = 0; ch < 2; ++ch) {
                buffers[ch] = event.inputBuffer.getChannelData(ch);
            }
            return buffers;
        },

        detectSilence: function () {

            this.listener.getByteFrequencyData(this.volumeData);

            let sum = 0;
            for (var vindex =0; vindex <this.volumeData.length;vindex++) {
                sum += this.volumeData[vindex] * this.volumeData[vindex];
            }

            var volume = Math.sqrt(sum / this.volumeData.length);
           // log.debug("volume: " + volume + ', hadsound: ' + this.alreadyhadsound);
            //if we already had a sound, we are looking for end of speech
            if(volume < this.silencelevel && this.alreadyhadsound){
                this.silencecount++;
                if(this.silencecount>=this.silenceintervals){
                    this.therecorder.silence_detected();
                }
            //if we have a sound, reset silence count to zero, and flag that we have started
            }else if(volume > this.silencelevel){
                this.alreadyhadsound = true;
                this.silencecount=0;
            }
        },

        drawWave: function() {

            var width = this.canvas.width() * 2;
            this.listener.getByteTimeDomainData(this.analyserData);

            this.canvasCtx.fillStyle = 'white';
            this.canvasCtx.fillRect(0, 0, width, this.waveHeight*2);

            this.canvasCtx.lineWidth = 5;
            this.canvasCtx.strokeStyle = 'gray';
            this.canvasCtx.beginPath();

            var slicewaveWidth = width / this.bufferLength;
            var x = 0;

            for (var i = 0; i < this.bufferLength; i++) {

                var v = this.analyserData[i] / 128.0;
                var y = v * this.waveHeight;

                if (i === 0) {
                    // this.canvasCtx.moveTo(x, y);
                } else {
                    this.canvasCtx.lineTo(x, y);
                }

                x += slicewaveWidth;
            }

            this.canvasCtx.lineTo(width, this.waveHeight);
            this.canvasCtx.stroke();

        }
    }; //end of this declaration


});