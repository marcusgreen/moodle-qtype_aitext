/* jshint ignore:start */
define(['jquery', 'core/log'], function ($, log) {

    "use strict"; // jshint ;_;

    log.debug('qtype_aitext browser speech rec: initialising');

    return {

        recognition: null,
        recognizing: false,
        final_transcript: '',
        interim_transcript: '',
        start_timestamp: 0,
        lang: 'en-US',
        interval: 0,


        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        will_work_ok: function(opts){
            //Edge and Safari both have browser recognition, but it's not good enough and we need to test it better (2021-11-21)
            var brave = typeof navigator.brave !== 'undefined';
            if(brave){return false;}

            var edge = navigator.userAgent.toLowerCase().indexOf("edg/") > -1;
            if(edge){return false;}

            var has_chrome = navigator.userAgent.indexOf('Chrome') > -1;
            var has_safari = navigator.userAgent.indexOf("Safari") > -1;
            var safari = has_safari && !has_chrome;
            if(safari){return false;}

            //This is feature detection, and for chrome its ok. the others might say they do speech rec, but its hard to be sure
            return ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window);

        },

        init: function (lang,waveheight,uniqueid) {
            log.debug('bh : ' + uniqueid);
            var SpeechRecognition = SpeechRecognition || webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.lang = lang;
            this.waveHeight = waveheight;
            this.uniqueid = uniqueid;
            this.prepare_html();
            this.register_events();
        },

        prepare_html: function(){
            this.canvas =$('.' + this.uniqueid + "_waveform");
            this.canvasCtx = this.canvas[0].getContext("2d");
        },

        set_grammar: function (grammar) {
            var SpeechGrammarList = SpeechGrammarList || webkitSpeechGrammarList;
            if (SpeechGrammarList) {
                var speechRecognitionList = new SpeechGrammarList();
                speechRecognitionList.addFromString(grammar, 1);
                this.recognition.grammars = speechRecognitionList;
            }
        },

        start: function () {
            var that =this;

            //If we already started ignore this
            if (this.recognizing) {
                return;
            }
            this.recognizing = true;
            this.final_transcript = '';
            this.interim_transcript = '';
            this.recognition.lang = this.lang;//select_dialect.value;
            this.recognition.start();
            this.start_timestamp = Date.now();//event.timeStamp;
            that.onstart();


            //kick off animation
            that.interval = setInterval(function() {
                that.drawWave();
            }, 100);


        },
        stop: function () {
            var that=this;
            this.recognizing = false;
            this.recognition.stop();
            clearInterval(this.interval);
            this.canvasCtx.clearRect(0, 0, this.canvas.width()*2, this.waveHeight * 2);
            setTimeout(function() {
                that.onfinalspeechcapture(that.final_transcript);
            }, 1000);
            this.onend();
        },

        register_events: function () {

            var recognition = this.recognition;
            var that = this;

            recognition.onerror = function (event) {
                if (event.error == 'no-speech') {
                    log.debug('info_no_speech');
                }
                if (event.error == 'audio-capture') {
                    log.debug('info_no_microphone');
                }
                if (event.error == 'not-allowed') {
                    if (event.timeStamp - that.start_timestamp < 100) {
                        log.debug('info_blocked');
                    } else {
                        log.debug('info_denied');
                    }
                }
                that.onerror({error: {name: event.error}});
            };

            recognition.onend = function () {
                if(that.recognizing){
                    that.recognition.start();
                }

            };

            recognition.onresult = function (event) {
                for (var i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        that.final_transcript += event.results[i][0].transcript;
                    } else {
                        var provisional_transcript = that.final_transcript + event.results[i][0].transcript;
                        //the interim and final events do not arrive in sequence, we dont want the length going down, its weird
                        //so just dont respond when the sequence is wonky
                        if(provisional_transcript.length < that.interim_transcript.length){
                            return;
                        }else{
                            that.interim_transcript = provisional_transcript;
                        }
                        that.oninterimspeechcapture(that.interim_transcript);
                    }
                }

            };
        },//end of register events

        drawWave: function() {

            var width = this.canvas.width() * 2;
            var bufferLength=4096;

            this.canvasCtx.fillStyle = 'white';
            this.canvasCtx.fillRect(0, 0, width, this.waveHeight*2);

            this.canvasCtx.lineWidth = 5;
            this.canvasCtx.strokeStyle = 'gray';
            this.canvasCtx.beginPath();

            var slicewaveWidth = width / bufferLength;
            var x = 0;

            for (var i = 0; i < bufferLength; i++) {

                var v = ((Math.random() * 64) + 96) / 128.0;
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

        },

        onstart: function () {
            log.debug('started');
        },
        onerror: function () {
            log.debug('error');
        },
        onend: function () {
            log.debug('end');
        },
        onfinalspeechcapture: function (speechtext) {
            log.debug(speechtext);
        },
        oninterimspeechcapture: function (speechtext) {
            // log.debug(speechtext);
        }

    };//end of returned object
});//total end
