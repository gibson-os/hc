Ext.define('GibsonOS.module.hc.io.model.DirectConnect', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int',
    },{
        name: 'inputPort',
        type: 'object'
    },{
        name: 'inputValue',
        type: 'bool'
    },{
        name: 'inputPortName',
        type: 'string',
        convert: function(value, record) {
            return record.get('inputPort').name;
        }
    },{
        name: 'outputPortId',
        type: 'int',
        useNull: true
    },{
        name: 'order',
        type: 'int',
        useNull: true
    },{
        name: 'value',
        type: 'bool',
        useNull: true
    },{
        name: 'pwm',
        type: 'int',
        useNull: true
    },{
        name: 'blink',
        type: 'int',
        useNull: true
    },{
        name: 'fadeIn',
        type: 'int',
        useNull: true
    },{
        name: 'addOrSub',
        type: 'int',
        useNull: true
    }]
});