Ext.define('GibsonOS.module.hc.warehouse.model.box.Item', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'description',
        type: 'string'
    },{
        name: 'image',
        type: 'string'
    },{
        name: 'stock',
        type: 'int'
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