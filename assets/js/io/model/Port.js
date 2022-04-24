Ext.define('GibsonOS.module.hc.io.model.Port', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'number',
        type: 'int'
    },{
        name: 'name',
        type: 'string'
    },{
        name: 'value',
        type: 'bool'
    },{
        name: 'direction',
        type: 'int'
    },{
        name: 'pullUp',
        type: 'bool'
    },{
        name: 'delay',
        type: 'int'
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
        name: 'valueNames',
        type: 'array'
    }]
});