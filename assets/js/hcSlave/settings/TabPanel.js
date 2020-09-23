Ext.define('GibsonOS.module.hc.hcSlave.settings.TabPanel', {
    extend: 'GibsonOS.TabPanel',
    alias: ['widget.gosModuleHcHcSlaveSettingsTabPanel'],
    initComponent: function() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcHcSlaveSettingsGeneralForm',
            title: 'Allgemein',
            gos: me.gos
        },{
            xtype: 'gosModuleHcHcSlaveSettingsEepromForm',
            title: 'EEPROM',
            gos: me.gos
        },{
            xtype: 'gosModuleHcHcSlaveSettingsStatusLedForm',
            title: 'Status LEDs',
            gos: me.gos
        }];

        me.callParent();
    }
});