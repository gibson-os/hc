Ext.define('GibsonOS.module.hc.warehouse.model.Box', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'uuid',
        type: 'string'
    },{
        name: 'left',
        type: 'int'
    },{
        name: 'top',
        type: 'int'
    },{
        name: 'width',
        type: 'int'
    },{
        name: 'height',
        type: 'int'
    },{
        name: 'leds',
        type: 'array'
    },{
        name: 'items',
        type: 'array'
    }]
});