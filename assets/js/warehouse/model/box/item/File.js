Ext.define('GibsonOS.module.hc.warehouse.model.box.item.File', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'fileName',
        type: 'string'
    },{
        name: 'mimeType',
        type: 'string'
    },{
        name: 'file',
        type: 'object',
        useNull: true
    }]
});