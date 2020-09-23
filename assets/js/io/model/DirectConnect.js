Ext.define('GibsonOS.module.hc.io.model.DirectConnect', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'inputPort',
        type: 'int',
    },{
        name: 'inputPortName',
        type: 'string'
    },{
        name: 'inputPortValue',
        type: 'int',
        useNull: true
    },{
        name: 'outputPortNumber',
        type: 'string'
    },{
        name: 'outputPort',
        type: 'int',
        useNull: true
    },{
        name: 'valueNames',
        type: 'array'
    },{
        name: 'order',
        type: 'int'
    },{
        name: 'value',
        type: 'int',
        useNull: true
    },{
        name: 'pwm',
        type: 'int'
    },{
        name: 'blink',
        type: 'int'
    },{
        name: 'fadeIn',
        type: 'int'
    },{
        name: 'addOrSub',
        type: 'int',
        useNull: true
    }]
});