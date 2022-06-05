Ext.define('GibsonOS.module.hc.neopixel.model.animation.Led', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'ledId',
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