Ext.define('GibsonOS.module.hc.hcSlave.settings.statusLed.FieldsetLeds', {
    extend: 'GibsonOS.form.Fieldset',
    alias: ['widget.gosModuleHcHcSlaveSettingsStatusLedFieldsetLeds'],
    title: 'LEDs',
    initComponent: function() {
        let me = this;

        me.items = [{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Power',
            name: 'power',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Fehler',
            name: 'error',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Verbinden',
            name: 'connect',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Übertragen',
            name: 'transreceive',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Senden',
            name: 'transceive',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Empfangen',
            name: 'receive',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        },{
            xtype: 'gosFormCheckbox',
            fieldLabel: 'Individuell',
            name: 'custom',
            inputValue: true,
            uncheckedValue: false,
            fieldStyle: 'float:right;'
        }];

        me.callParent();
    }
});