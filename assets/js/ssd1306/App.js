Ext.define('GibsonOS.module.hc.ssd1306.App', {
    extend: 'GibsonOS.module.hc.slave.App',
    alias: ['widget.gosModuleHcSsd1306App'],
    title: 'SSD1306 - Display',
    appIcon: 'icon_homecontrol',
    width: 900,
    height: 550,
    initComponent() {
        const me = this;

        me.items = [{
            xtype: 'gosModuleHcSsd1306View',
            title: 'Display'
        }];

        me.callParent();
    }
});