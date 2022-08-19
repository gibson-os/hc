Ext.define('GibsonOS.module.hc.warehouse.model.label.Element', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'top',
        type: 'float'
    },{
        name: 'left',
        type: 'float'
    },{
        name: 'width',
        type: 'float'
    },{
        name: 'height',
        type: 'float'
    },{
        name: 'color',
        type: 'string',
        useNull: true
    },{
        name: 'backgroundColor',
        type: 'string',
        useNull: true
    },{
        name: 'type',
        type: 'string'
    },{
        name: 'options',
        type: 'object'
    }]
});