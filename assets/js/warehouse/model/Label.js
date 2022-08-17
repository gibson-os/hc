Ext.define('GibsonOS.module.hc.warehouse.model.Label', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'elements',
        type: 'array'
    }]
});