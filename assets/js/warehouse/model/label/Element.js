Ext.define('GibsonOS.module.hc.warehouse.model.label.Element', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'top',
        type: 'int'
    },{
        name: 'left',
        type: 'int'
    },{
        name: 'width',
        type: 'int'
    },{
        name: 'height',
        type: 'int'
    },{
        name: 'color',
        type: 'string'
    },{
        name: 'backgroundColor',
        type: 'string'
    },{
        name: 'type',
        type: 'string'
    },{
        name: 'options',
        type: 'object'
    }]
});