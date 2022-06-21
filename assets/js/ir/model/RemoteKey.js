Ext.define('GibsonOS.module.hc.ir.model.RemoteKey', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'width',
        type: 'int',
        useNull: true
    },{
        name: 'height',
        type: 'int',
        useNull: true
    },{
        name: 'top',
        type: 'int',
        useNull: true
    },{
        name: 'left',
        type: 'int',
        useNull: true
    },{
        name: 'name',
        type: 'string',
        useNull: true
    },{
        name: 'borderTop',
        type: 'bool',
        defaultValue: true
    },{
        name: 'borderRight',
        type: 'bool',
        defaultValue: true
    },{
        name: 'borderBottom',
        type: 'bool',
        defaultValue: true
    },{
        name: 'borderLeft',
        type: 'bool',
        defaultValue: true
    },{
        name: 'borderRadiusTopLeft',
        type: 'int',
        defaultValue: 0
    },{
        name: 'borderRadiusTopRight',
        type: 'int',
        defaultValue: 0
    },{
        name: 'borderRadiusBottomLeft',
        type: 'int',
        defaultValue: 0
    },{
        name: 'borderRadiusBottomRight',
        type: 'int',
        defaultValue: 0
    },{
        name: 'background',
        type: 'string',
        useNull: true
    },{
        name: 'event',
        type: 'object',
        useNull: true
    },{
        name: 'keys',
        type: 'array',
        defaultValue: []
    }]
});