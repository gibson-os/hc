Ext.define('GibsonOS.module.hc.ir.model.RemoteKey', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'width',
        type: 'int'
    },{
        name: 'height',
        type: 'int'
    },{
        name: 'top',
        type: 'int'
    },{
        name: 'left',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'borderTop',
        type: 'bool'
    },{
        name: 'borderRight',
        type: 'bool'
    },{
        name: 'borderBottom',
        type: 'bool'
    },{
        name: 'borderLeft',
        type: 'bool'
    },{
        name: 'borderRadiusTopLeft',
        type: 'int'
    },{
        name: 'borderRadiusTopRight',
        type: 'int'
    },{
        name: 'borderRadiusBottomLeft',
        type: 'int'
    },{
        name: 'borderRadiusBottomRight',
        type: 'int'
    },{
        name: 'eventId',
        type: 'int'
    }],
    // hasMany: {
    //     model: 'GibsonOS.module.hc.ir.model.Key',
    //     name: 'keys'
    // }
});