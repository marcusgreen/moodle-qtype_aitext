define(['jquery', 'core/log','core/notification', 'qtype_aitext/audiohelper','qtype_aitext/browserrec','core/str' ],
    function ($, log, notification, audioHelper, browserRec,str) {
    "use strict"; // jshint ;_;
    /*
    *  The TT recorder
     */

    log.debug('qtype_aitext Audio Recorder: initialising');

    return {
        waveHeight: 75,
        audio: {
            stream: null,
            blob: null,
            dataURI: null,
            start: null,
            end: null,
            isRecording: false,
            isRecognizing: false,
            transcript: null
        },
        submitting: false,
        controls: {},
        uniqueid: null,
        audio_updated: null,
        maxtime: 15000,
        passagehash: null,
        region: null,
        asrurl: null,
        lang: null,
        browserrec: null,
        usebrowserrec: false,
        currentTime: 0,
        stt_guided: false,
        currentPrompt: false,
        strings: {},

        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function(opts){
            var that = this;
            this.uniqueid=opts['uniqueid'];
            this.callback=opts['callback'];
            this.stt_guided = opts['stt_guided'] ? opts['stt_guided'] : false;
            this.init_strings();
            this.prepare_html();
            this.controls.recordercontainer.show();
            this.register_events();


            //callbacks
            var on_error = function(error) {
                switch (error.name) {
                    case 'PermissionDeniedError':
                    case 'NotAllowedError':
                        notification.alert("Error",that.strings.allowmicaccess, "OK");
                        break;
                    case 'DevicesNotFoundError':
                    case 'NotFoundError':
                        notification.alert("Error",that.strings.nomicdetected, "OK");
                        break;
                    default:
                        //other errors, like from Edge can fire repeatedly so a notification is not a good idea
                        //notification.alert("Error", error.name, "OK");
                        log.debug("Error", error.name);
                }
            };

            var on_stopped = function(blob) {
                clearInterval(that.interval);

                //if the blob is undefined then the user is super clicking or something
                if(blob===undefined){
                    return;
                }

                //if ds recc
                var newaudio = {
                    blob: blob,
                    dataURI: URL.createObjectURL(blob),
                    end: new Date(),
                    isRecording: false,
                    length: Math.round((that.audio.end - that.audio.start) / 1000),
                };
                that.update_audio(newaudio);

                that.deepSpeech2(that.audio.blob, function(response){
                    log.debug(response);
                    if(response.data.result==="success" && response.data.transcript){
                        that.gotRecognition(response.data.transcript.trim());
                    } else {
                        notification.alert("Information",that.strings.speechnotrecognized, "OK");
                    }
                    that.update_audio('isRecognizing',false);
                });

            };

            //set up events
            var on_gotstream=  function(stream) {
                //clear any existing interval
                if(that.interval!==undefined){
                    clearInterval(that.interval);
                }

                var newaudio={stream: stream, isRecording: true};
                that.update_audio(newaudio);
                that.currentTime = 0;
                //if we have a time limit run an interval to check ever 10ms if we should stop
                if (that.maxtime>0) {
                    that.interval = setInterval(function () {
                        if (that.currentTime < that.maxtime) {
                            that.currentTime += 10;
                        } else {
                            that.update_audio('isRecognizing', true);
                            // vm.isRecognizing = true;
                            that.audiohelper.stop();
                        }
                    }, 10);
                }
            };

            //If browser rec (Chrome Speech Rec) (and ds is optiona)
            if(browserRec.will_work_ok() && ! this.stt_guided){
                //Init browserrec
                log.debug("using browser rec");
                log.debug('arh : ' + that.uniqueid);
                that.browserrec = browserRec.clone();
                log.debug('arh : ' + that.uniqueid);
                that.browserrec.init(that.lang,that.waveHeight,that.uniqueid);
                that.usebrowserrec=true;

                //set up events
                that.browserrec.onerror = on_error;
                that.browserrec.onend = function(){
                        //do something here
                };
                that.browserrec.onstart = function(){
                    //do something here
                };
                that.browserrec.onfinalspeechcapture=function(speechtext){
                    that.gotRecognition(speechtext);
                    that.update_audio('isRecording',false);
                    that.update_audio('isRecognizing',false);
                };

                that.browserrec.oninterimspeechcapture=function(speechtext){
                    that.gotInterimRecognition(speechtext);
                };

            //If DS rec
            }else {
                //set up wav for ds rec
                log.debug("using ds rec");
                this.audiohelper =  audioHelper.clone();
                this.audiohelper.init(this.waveHeight,this.uniqueid,this);

                that.audiohelper.onError = on_error;
                that.audiohelper.onStop = on_stopped;
                that.audiohelper.onStream = on_gotstream;

            }//end of setting up recorders
        },

        init_strings: function(){
            var that=this;
            str.get_strings([
                { "key": "allowmicaccess", "component": 'mod_minilesson'},
                { "key": "nomicdetected", "component": 'mod_minilesson'},
                { "key": "speechnotrecognized", "component": 'mod_minilesson'},

            ]).done(function (s) {
                var i = 0;
                that.strings.allowmicaccess = s[i++];
                that.strings.nomicdetected = s[i++];
                that.strings.speechnotrecognized = s[i++];
            });
        },

        prepare_html: function(){
            this.controls.recordercontainer =$('.audiorec_container_' + this.uniqueid);
            this.controls.recorderbutton = $('.' + this.uniqueid + '_recorderdiv');
            this.passagehash = this.controls.recorderbutton.data('passagehash');
            this.region=this.controls.recorderbutton.data('region');
            this.lang=this.controls.recorderbutton.data('lang');
            this.asrurl=this.controls.recorderbutton.data('asrurl');
            this.maxtime=this.controls.recorderbutton.data('maxtime');
            this.waveHeight=this.controls.recorderbutton.data('waveheight');
        },

        silence_detected: function(){
            if(this.audio.isRecording){
                this.toggleRecording();
            }
        },

        update_audio: function(newprops,val){
            if (typeof newprops === 'string') {
                log.debug('update_audio:' + newprops + ':' + val);
                if (this.audio[newprops] !== val) {
                    this.audio[newprops] = val;
                    this.audio_updated();
                }
            }else{
                for (var theprop in newprops) {
                    this.audio[theprop] = newprops[theprop];
                    log.debug('update_audio:' + theprop + ':' + newprops[theprop]);
                }
                this.audio_updated();
            }
        },

        register_events: function(){
            var that = this;
            this.controls.recordercontainer.click(function(){
                that.toggleRecording();
            });

            this.audio_updated=function() {
                //pointer
                if (that.audio.isRecognizing) {
                    that.show_recorder_pointer('none');
                } else {
                    that.show_recorder_pointer('auto');
                }

                if(that.audio.isRecognizing || that.audio.isRecording ) {
                    this.controls.recorderbutton.css('background', '#e52');
                }else{
                    this.controls.recorderbutton.css('background', 'green');
                }

                //div content WHEN?
                that.controls.recorderbutton.html(that.recordBtnContent());
            };

        },

        show_recorder_pointer: function(show){
            if(show) {
                this.controls.recorderbutton.css('pointer-events', 'none');
            }else{
                this.controls.recorderbutton.css('pointer-events', 'auto');
            }

        },


        gotRecognition:function(transcript){
            log.debug('transcript:' + transcript);
            var message={};
            message.type='speech';
            message.capturedspeech = transcript;
           //POINT
            this.callback(message);
        },

        gotInterimRecognition:function(transcript){
            var message={};
            message.type='interimspeech';
            message.capturedspeech = transcript;
            //POINT
            this.callback(message);
        },

        cleanWord: function(word) {
            return word.replace(/['!"#$%&\\'()\*+,\-\.\/:;<=>?@\[\\\]\^_`{|}~']/g,"").toLowerCase();
        },

        recordBtnContent: function() {

            if(!this.audio.isRecognizing){

                if (this.audio.isRecording) {
                    return '<i class="fa fa-stop">';
                } else {
                    return '<i class="fa fa-microphone">';
                }

            } else {
                return '<i class="fa fa-spinner fa-spin">';
            }
        },
        toggleRecording: function() {
            var that =this;
            //If we are recognizing, then we want to discourage super click'ers
            if (this.audio.isRecognizing) {
                return;
            }

            //If we are current recording
            if (this.audio.isRecording) {
                //If using Browser Rec (chrome speech)
                if(this.usebrowserrec){
                    clearInterval(that.interval);
                    that.update_audio('isRecording',false);
                    that.update_audio('isRecognizing',true);
                    this.browserrec.stop();

                //If using DS rec
                }else{
                    this.update_audio('isRecognizing',true);
                    this.audiohelper.stop();
                }

             //If we are NOT currently recording
            } else {

                //If using Browser Rec (chrome speech)
                if(this.usebrowserrec){
                    this.update_audio('isRecording',true);
                    this.browserrec.start();
                    that.currentTime = 0;
                    //if we have a time limit run an interval to check ever 10ms if we should stop
                    if(that.maxtime>0) {
                        this.interval = setInterval(function () {
                            if (that.currentTime < that.maxtime) {
                                that.currentTime += 10;
                            } else {
                                that.update_audio('isRecording', false);
                                that.update_audio('isRecognizing', true);
                                clearInterval(that.interval);
                                that.browserrec.stop();
                            }
                        }, 10);
                    }

                //If using DS Rec
                }else {
                    var newaudio = {
                        stream: null,
                        blob: null,
                        dataURI: null,
                        start: new Date(),
                        end: null,
                        isRecording: false,
                        isRecognizing:false,
                        transcript: null
                    };
                    this.update_audio(newaudio);
                    this.audiohelper.start();
                }
            }
        },


        deepSpeech2: function(blob, callback) {
            var bodyFormData = new FormData();
            var blobname = this.uniqueid + Math.floor(Math.random() * 100) +  '.wav';
            bodyFormData.append('audioFile', blob, blobname);
            bodyFormData.append('scorer', this.passagehash);
            if(this.stt_guided) {
                bodyFormData.append('strictmode', 'false');
            }else{
                bodyFormData.append('strictmode', 'true');
            }
            //prompt is used by whisper and other transcibers down the line
            if(this.currentPrompt!==false){
                bodyFormData.append('prompt', this.currentPrompt);
            }
            bodyFormData.append('lang', this.lang);
            bodyFormData.append('wwwroot', M.cfg.wwwroot);

            var oReq = new XMLHttpRequest();
            oReq.open("POST", this.asrurl, true);
            oReq.onUploadProgress= function(progressEvent) {};
            oReq.onload = function(oEvent) {
                if (oReq.status === 200) {
                    callback(JSON.parse(oReq.response));
                } else {
                    callback({data: {result: "error"}});
                    log.debug(oReq.error);
                }
            };
            try {
                oReq.send(bodyFormData);
            }catch(err){
                callback({data: {result: "error"}});
                log.debug(err);
            }
        },

    };//end of return value

});