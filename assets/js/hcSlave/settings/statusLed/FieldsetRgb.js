Ext.define('GibsonOS.module.hc.hcSlave.settings.statusLed.FieldsetRgb', {
    extend: 'GibsonOS.form.Fieldset',
    alias: ['widget.gosModuleHcHcSlaveSettingsStatusLedFieldsetRgb'],
    title: 'RGB',
    initComponent: function() {
        let me = this;

        me.items = [{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Power',
            name: 'powerCode'
        },{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Fehler',
            name: 'errorCode'
        },{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Verbinden',
            name: 'connectCode'
        },{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Senden',
            name: 'transceiveCode'
        },{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Empfangen',
            name: 'receiveCode'
        },{
            xtype: 'gosFormTextfield',
            fieldLabel: 'Individuell',
            name: 'customCode'
        }];

        me.callParent();
    }
});