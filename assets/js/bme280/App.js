Ext.define('GibsonOS.module.hc.bme280.App', {
    extend: 'GibsonOS.module.hc.slave.App',
    alias: ['widget.gosModuleHcBme280App'],
    title: 'BME280 - Luftsensor',
    appIcon: 'icon_homecontrol',
    width: 300,
    height: 300,
    initComponent: function() {
        var me = this;

        me.dataUpdateActive = true;
        me.dataUrl = baseDir + 'hc/bme280/status';
        me.dataParams = {
            moduleId: me.gos.data.module.id
        };

        me.items = [{
            xtype: 'gosModuleHcBme280Panel',
            title: 'Neues Modul',
            gos: me.gos
        }];

        me.callParent();
        me.on('updatestatus', function(window, data) {
            me.down('gosModuleHcBme280Panel').update(data);
        });
    }
});