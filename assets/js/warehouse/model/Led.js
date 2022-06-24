Ext.define('GibsonOS.module.hc.warehouse.model.Led', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'led',
        type: 'object'
    },{
        name: 'number',
        type: 'int',
        convert: function(value, record) {
            return record.get('led').number;
        }
    }]
});