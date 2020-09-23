Ext.define('GibsonOS.module.hc.index.model.Module', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'type_id',
        type: 'int'
    },{
        name: 'type',
        type: 'string'
    },{
        name: 'hertz',
        type: 'int'
    },{
        name: 'helper',
        type: 'string'
    },{
        name: 'address',
        type: 'int'
    },{
        name: 'offline',
        type: 'int'
    },{
        name: 'settings',
        type: 'object'
    },{
        name: 'added',
        type: 'string'
    },{
        name: 'modified',
        type: 'string'
    }]
});