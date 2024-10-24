define(['jquery',
    'core/log',
    'qtype_aitext/audiorecorder'
    ], function($, log,  audiorecorder) {
  "use strict"; // jshint ;_;

  /*
  This file is to manage the quiz stage
   */

  log.debug('qtype_aitext transcriptmanager: initialising');

  return {

      //a handle on the audio recorder
      audiorec: null,
      
      //for making multiple instances .. for making multiple instances .. for making multiple instances .. multiple..
      clone: function () {
          return $.extend(true, {}, this);
     },

    init: function(opts) {
      this.register_events(opts);
      this.init_components(opts);
    },

    register_events: function(opts) {
      var self = this;
      $('.retry_' + opts.uniqueid).on('click', function() {
        $('.qtype_aitext_audiorecorder_' + opts.uniqueid).removeClass('hidden');
        $('.qtype_aitext_audiosummary_' + opts.uniqueid).addClass('hidden');
      });
    },//end of register events

    init_components: function(opts) {
        var app= this;
        var theCallback = function(message) {

            switch (message.type) {
                case 'recording':
                    break;
                case 'interimspeech':
                    var wordcount = app.count_words(message.capturedspeech);
                    $('.' + opts.uniqueid + '_currentwordcount').text(wordcount);
                    break;
                case 'speech':
                    log.debug("speech at multiaudio");
                    var speechtext = message.capturedspeech;

                    //set speech text to the hidden input
                    $('.' + opts.uniqueid).val(speechtext);

                    //update the wordcount
                    var wordcount = app.count_words(message.capturedspeech);
                    $('.' + opts.uniqueid + '_currentwordcount').text(wordcount);

                    //hide the recorder and show the summary
                    $('.qtype_aitext_audiorecorder_' + opts.uniqueid).addClass('hidden');
                    $('.qtype_aitext_audiosummary_' + opts.uniqueid).removeClass('hidden');

                    log.debug('speechtext:',speechtext);
            } //end of switch message type
        };

        //init audio recorder
        opts.callback = theCallback;
        opts.stt_guided=false;
        app.audiorec = audiorecorder.clone();
        app.audiorec.init(opts);
    }, //end of init components

    count_words: function(transcript) {
        return transcript.trim().split(/\s+/).filter(function(word) {
            return word.length > 0;
        }).length;
    }
  };
});