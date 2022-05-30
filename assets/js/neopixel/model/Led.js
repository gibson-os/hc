Ext.define('GibsonOS.module.hc.neopixel.model.Led', {
    extend: 'GibsonOS.data.Model',
    fields: [{
        name: 'id',
        type: 'int'
    },{
        name: 'number',
        type: 'int'
    },{
        name: 'channel',
        type: 'int'
    },{
        name: 'left',
        type: 'int'
    },{
        name: 'top',
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
        name: 'deactivated',
        type: 'bool'
    }]
});