/**
 * A tool for hiding sections of text on the page
 *
 * @module moodle-report_eventlist-sectionHide
 */

/**
 * A tool for hiding sections of text on the page
 *
 * @class M.report_eventlist.SectionHide
 * @extends Base
 * @constructor
 */
function SectionHide() {
    SectionHide.superclass.constructor.apply(this, arguments);
}

var SELECTORS = {
        CODELINK : '#event-show-more',
        CODEBOX : '#id_event-code'
    };

Y.extend(SectionHide, Y.Base, {

    /**
     * Initializer.
     * Basic setup and delegations.
     *
     * @method initializer
     */
    initializer: function() {
        var codelink = Y.one(SELECTORS.CODELINK);

        codelink.on('click', this._changeVisibility, this);
    },

    /**
     * Change the visibility of the event code.
     *
     * @method _changeVisibility
     * @private
     */
    _changeVisibility: function() {
        var codesection = Y.one(SELECTORS.CODEBOX);
        if (codesection.hasClass('path-admin-tool-eventlist-code-hidden')) {
            codesection.removeClass('path-admin-tool-eventlist-code-hidden');
        } else {
            codesection.addClass('path-admin-tool-eventlist-code-hidden');
        }
    }
}, {
    NAME: 'sectionHide',
    ATTRS: {
    }
});

Y.namespace('M.report_eventlist.SectionHide').init = function(config) {
    return new SectionHide(config);
};
