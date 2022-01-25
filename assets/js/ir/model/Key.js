Ext.define('GibsonOS.module.hc.ir.model.Key', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'protocol',
        type: 'int'
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