Ext.define('GibsonOS.module.hc.neopixel.color.Blink', {
    extend: 'GibsonOS.form.ComboBox',
    alias: ['widget.gosModuleHcNeopixelColorBlink'],
    fieldLabel: 'Blinken',
    emptyText: 'Nicht',
    store: {
        xtype: 'gosDataStore',
        fields: [{
            name: 'id',
            type: 'int'
        },{
            name: 'name',
            type: 'string'
        }],
        data: [{
            id: 0,
            name: 'Nicht'
        },{
            id: 1,
            name: 'Verdammt langsam'
        },{
            id: 2,
            name: 'Extrem langsam'
        },{
            id: 3,
            name: 'Sehr sehr langsam'
        },{
            id: 4,
            name: 'Sehr langsam'
        },{
            id: 5,
            name: 'Ganz langsam'
        },{
            id: 6,
            name: 'Langsamer'
        },{
            id: 7,
            name: 'Langsam'
        },{
            id: 8,
            name: 'Normal'
        },{
            id: 9,
            name: 'Schnell'
        },{
            id: 10,
            name: 'Schneller'
        },{
            id: 11,
            name: 'Ganz schnell'
        },{
            id: 12,
            name: 'Sehr schnell'
        },{
            id: 13,
            name: 'Sehr sehr schnell'
        },{
            id: 14,
            name: 'Extrem schnell'
        },{
            id: 15,
            name: 'Verdammt schnell'
        }]
    },
    setValuesByPwmSpeed(pwmSpeed) {
        const me = this;

        me.getStore().each(record => {
            if (!record.get('id')) {
                return true;
            }

            const seconds = 1 / pwmSpeed * (1 << record.get('id')) * 2;
            record.set('seconds', seconds);
            record.set('name', transformSeconds(seconds));
        });
    }
});