Ext.define('GibsonOS.module.hc.ir.model.RemoteKey', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
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
        name: 'style',
        type: 'int'
    },{
        name: 'background',
        type: 'string'
    },{
        name: 'docked',
        type: 'string'
    },{
        name: 'eventId',
        type: 'int'
    }],
    // hasMany: {
    //     model: 'GibsonOS.module.hc.ir.model.Key',
    //     name: 'keys'
    // }
});