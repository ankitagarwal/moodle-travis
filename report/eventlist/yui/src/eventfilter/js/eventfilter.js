/**
 * A tool for displaying and filtering system events.
 *
 * @module moodle-report_eventlist-eventfilter
 */

/**
 * A tool for displaying and filtering system events.
 *
 * @class M.report_eventlist.EventFilter
 * @extends Base
 * @constructor
 */
function EventFilter() {
    EventFilter.superclass.constructor.apply(this, arguments);
}

var SELECTORS = {
        EVENTNAME: '#id_eventname',
        EVENTCOMPONENT: '#id_eventcomponent',
        EVENTEDULEVEL: '#id_eventedulevel',
        EVENTCRUD: '#id_eventcrud',
        FILTERBUTTON : '#id_filterbutton',
        CLEARBUTTON : '#id_clearbutton'
    };

Y.extend(EventFilter, Y.Base, {

    /**
     * A reference to the datatable.
     *
     * @property _table
     * @type DataTable
     * @private
     */
    _table: null,
    /**
     * A reference to the eventname text element.
     *
     * @property _eventName
     * @type node
     * @private
     */
    _eventName: null,
    /**
     * A reference to the component select box element.
     *
     * @property _component
     * @type node
     * @private
     */
    _component: null,
    /**
     * A reference to the education level select box element.
     *
     * @property _eduLevel
     * @type node
     * @private
     */
    _eduLevel: null,
    /**
     * A reference to the CRUD select box element.
     *
     * @property _crud
     * @type node
     * @private
     */
    _crud: null,

    /**
     * Initializer.
     * Basic setup and delegations.
     *
     * @method initializer
     */
    initializer: function() {

        var filterButton = Y.one(SELECTORS.FILTERBUTTON),
            clearButton = Y.one(SELECTORS.CLEARBUTTON);

        this._createTable(this.get('tabledata'));
        this._eventName = Y.one(SELECTORS.EVENTNAME);
        this._component = Y.one(SELECTORS.EVENTCOMPONENT);
        this._eduLevel = Y.one(SELECTORS.EVENTEDULEVEL);
        this._crud = Y.one(SELECTORS.EVENTCRUD);

        this._eventName.on('valuechange', this._totalFilter, this);
        filterButton.on('click', this._totalFilter, this);
        clearButton.on('click', this._clearFilter, this);
    },

    /**
     * Create the table for displaying all of the event information.
     *
     * @param {array} tableData Event data for populating the table.
     * @method _createTable
     * @private
     * @chainable
     */
    _createTable: function(tableData) {

        var table = new Y.DataTable({
            columns: [
                {
                    key: "fulleventname",
                    label: M.util.get_string('eventname', 'report_eventlist'),
                    allowHTML: true,
                    sortable: true
                }, {
                    key: "component",
                    label: M.util.get_string('component', 'report_eventlist'),
                    sortable: true
                }, {
                    key:  "edulevel",
                    label: M.util.get_string('edulevel', 'report_eventlist'),
                    sortable: true
                }, {
                    key: "crud",
                    label: M.util.get_string('crud', 'report_eventlist'),
                    sortable: true
                }, {
                    key: "action",
                    label: M.util.get_string('action', 'report_eventlist'),
                    sortable: true
                }, {
                    key: "objecttable",
                    label: M.util.get_string('objecttable', 'report_eventlist'),
                    sortable: true
                }, {
                    key: "devdetail",
                    label:M.util.get_string('devdetail', 'report_eventlist'),
                    allowHTML: true,
                    sortable: false
                }
            ],
            data: tableData
        });


        // Display the table.
        table.render("#path-admin-tool-eventlist-table");
        table.get('boundingBox').addClass('path-admin-tool-eventlist-datatable-table');
        this._table = table;
        return this;
    },

    /**
     * Filters the entries being displayed in the table.
     *
     * @method totalFilter
     * @private
     */
    _totalFilter: function() {
        // Get all of the details of the filter elements
        var eventNameFilter = this._eventName.get('value').toLowerCase(),
            // Component selected text.
            componentFilter = this._component.get('options').item(this._component.get('selectedIndex')).get('text').toLowerCase(),
            // Component selected value.
            componentValue = this._component.get('value'),
            // Education level selected text.
            eduLevelFilter = this._eduLevel.get('options').item(this._eduLevel.get('selectedIndex')).get('text').toLowerCase(),
            // Education level selected value.
            eduLevelValue = this._eduLevel.get('value'),
            // CRUD selected text.
            crudFilter = this._crud.get('options').item(this._crud.get('selectedIndex')).get('text').toLowerCase(),
            // CRUD selected value.
            crudValue = this._crud.get('value'),
            i,
            filtered = [];

        // Loop through the rows and put the ones we want into the filter.
        for (i = 0; i < this.get('tabledata').length; i++) {
            // These variables will either be false or true depending on the statement outcome.
            var eventNameValue = this.get('tabledata')[i].fulleventname.toLowerCase().indexOf(eventNameFilter) >= 0,
                componentFilterValue = this.get('tabledata')[i].component.toLowerCase().indexOf(componentFilter) >= 0,
                eduLevelFilterValue = this.get('tabledata')[i].edulevel.toLowerCase().indexOf(eduLevelFilter) >= 0,
                crudFilterValue = this.get('tabledata')[i].crud.toLowerCase().indexOf(crudFilter) >= 0;
            // If the name field is empty then add to the filter.
            if (eventNameFilter === '') {
                eventNameValue = true;
            }
            // If the component is set to 'all' then add to the filter.
            if (componentValue === '0') {
                componentFilterValue = true;
            }
            // If the education level is set to 'all' then add to the filter.
            if (eduLevelValue === '0') {
                eduLevelFilterValue = true;
            }
            // If the CRUD field is set to 'all' then add to the filter.
            if (crudValue === '0') {
                crudFilterValue = true;
            }
            // If any of the Values here is false then don't add to the filter (all must be true).
            if (eventNameValue && componentFilterValue && eduLevelFilterValue && crudFilterValue) {
                filtered.push(this.get('tabledata')[i]);
            }
        }
        // Display the table again with the new data.
        this._table.set('data', filtered);
    },

    /**
     * Clears the filtered table data and changes the filter form to default.
     *
     * @method _clearFilter
     * @private
     */
    _clearFilter: function() {
        // Reset filter form elements
        this._eventName.set('value', '');
        this._component.set('value', '0');
        this._eduLevel.set('value', '0');
        this._crud.set('value', '0');
        // Reset the table data back to the original.
        this._table.set('data', this.get('tabledata'));
    }
}, {
    NAME: 'eventFilter',
    ATTRS: {
        /**
         * Data for the table.
         *
         * @attribute tabledata.
         * @type Array
         * @writeOnce
         */
        tabledata: {
            value: null
        }
    }
});

Y.namespace('M.report_eventlist.EventFilter').init = function(config) {
    return new EventFilter(config);
};
