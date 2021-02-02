Ext.define('GibsonOS.module.hc.index.model.Log', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'master',
        type: 'string'
    },{
        name: 'module',
        type: 'string'
    },{
        name: 'direction',
        type: 'int'
    },{
        name: 'type',
        type: 'int'
    },{
        name: 'command',
        type: 'string'
    },{
        name: 'plain',
        type: 'string'
    },{
        name: 'raw',
        type: 'string'
    },{
        name: 'text',
        type: 'string'
    },{
        name: 'rendered',
        type: 'string'
    },{
        name: 'added',
        type: 'string'
    }]
});