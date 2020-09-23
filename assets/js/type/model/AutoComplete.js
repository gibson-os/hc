Ext.define('GibsonOS.module.hc.type.model.AutoComplete', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'helper',
        type: 'string'
    },{
        name: 'network',
        type: 'int'
    },{
        name: 'isHcSlave',
        type: 'bool'
    }]
});