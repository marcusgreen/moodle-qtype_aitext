define(['jquery', 'core/log'], function ($, log) {
    "use strict"; // jshint ;_;
    /*
    This file is the engine that drives audio rec and canvas drawing. TT Recorder is the just the glory kid
     */

    log.debug('qtype_aitext Wav Encoder initialising');

    return {


        //for making multiple instances
        clone: function () {
            return $.extend(true, {}, this);
        },

        init: function(sampleRate, numChannels) {
            this.sampleRate = sampleRate;
            this.numChannels = numChannels;
            this.numSamples = 0;
            this.dataViews = [];
        },

        encode: function(buffer) {
            //this would be an event that occurs after recorder has stopped lets just ignore it
            if(this.dataViews===undefined){
                return;
            }

            var len = buffer[0].length,
                nCh = this.numChannels,
                view = new DataView(new ArrayBuffer(len * nCh * 2)),
                offset = 0;
            for (var i = 0; i < len; ++i) {
                for (var ch = 0; ch < nCh; ++ch) {
                    var x = buffer[ch][i] * 0x7fff;
                    view.setInt16(offset, x < 0 ? Math.max(x, -0x8000) : Math.min(x, 0x7fff), true);
                    offset += 2;
                }
            }
            this.dataViews.push(view);
            this.numSamples += len;
        },

        setString: function(view, offset, str) {
            var len = str.length;
            for (var i = 0; i < len; ++i) {
                view.setUint8(offset + i, str.charCodeAt(i));
            }
        },

        finish: function(mimeType) {

            //this would be an event that occurs after recorder has stopped lets just ignore it
            if(this.dataViews===undefined){
                return;
            }

            var dataSize = this.numChannels * this.numSamples * 2;
            var view = new DataView(new ArrayBuffer(44));
            this.setString(view, 0, 'RIFF');
            view.setUint32(4, 36 + dataSize, true);
            this.setString(view, 8, 'WAVE');
            this.setString(view, 12, 'fmt ');
            view.setUint32(16, 16, true);
            view.setUint16(20, 1, true);
            view.setUint16(22, this.numChannels, true);
            view.setUint32(24, this.sampleRate, true);
            view.setUint32(28, this.sampleRate * 4, true);
            view.setUint16(32, this.numChannels * 2, true);
            view.setUint16(34, 16, true);
            this.setString(view, 36, 'data');
            view.setUint32(40, dataSize, true);
            this.dataViews.unshift(view);
            var blob = new Blob(this.dataViews, { type: 'audio/wav' });
            this.cleanup();
            return blob;
        },

        cancel: function() {
            delete this.dataViews;
        },

        cleanup: function() {
            this.cancel();
        }

     };//end of return value

});