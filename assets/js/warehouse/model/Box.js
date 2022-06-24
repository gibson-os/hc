Ext.define('GibsonOS.module.hc.warehouse.model.Box', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'image',
        type: 'string'
    },{
        name: 'code',
        type: 'string'
    },{
        name: 'stock',
        type: 'int'
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
        name: 'links',
        type: 'array'
    },{
        name: 'files',
        type: 'array'
    },{
        name: 'codes',
        type: 'array'
    },{
        name: 'tags',
        type: 'array'
    }]
});