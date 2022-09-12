Ext.define('GibsonOS.module.hc.index.model.Master', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'protocol',
        type: 'string'
    },{
        name: 'address',
        type: 'string'
    },{
        name: 'added',
        type: 'string'
    }, {
        name: 'modified',
        type: 'string'
    },{
        name: 'offline',
        type: 'bool'
    }]
});