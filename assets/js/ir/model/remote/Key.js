Ext.define('GibsonOS.module.hc.ir.model.remote.Key', {
    extend: 'GibsonOS.data.Model',
    idProperty: 'generatedId',
    fields: [{
        name: 'generatedId',
        useNull: true,
    },{
        name: 'id',
        type: 'int',
    },{
        name: 'address',
        type: 'int'
    },{
        name: 'command',
        type: 'int'
    },{
        name: 'protocolName',
        type: 'string'
    },{
        name: 'name',
        type: 'string'
    }]
});