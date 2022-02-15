Ext.define('GibsonOS.module.hc.index.model.Log', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'masterId',
        type: 'int'
    },{
        name: 'masterName',
        type: 'string'
    },{
        name: 'moduleId',
        type: 'int'
    },{
        name: 'moduleName',
        type: 'string'
    },{
        name: 'direction',
        type: 'string'
    },{
        name: 'type',
        type: 'int'
    },{
        name: 'command',
        type: 'string'
    },{
        name: 'data',
        type: 'string'
    },{
        name: 'text',
        type: 'string'
    },{
        name: 'rendered',
        type: 'string'
    },{
        name: 'explains',
        type: 'array'
    },{
        name: 'added',
        type: 'string'
    }]
});