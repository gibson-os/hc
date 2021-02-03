Ext.define('GibsonOS.module.hc.neopixel.model.Animation', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'number',
        type: 'int'
    },{
        name: 'red',
        type: 'int'
    },{
        name: 'green',
        type: 'int'
    },{
        name: 'blue',
        type: 'int'
    },{
        name: 'fadeIn',
        type: 'int'
    },{
        name: 'blink',
        type: 'int'
    },{
        name: 'time',
        type: 'int'
    },{
        name: 'length',
        type: 'int'
    },{
        name: 'deactivated',
        type: 'bool'
    }]
});