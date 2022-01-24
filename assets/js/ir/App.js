Ext.define('GibsonOS.module.hc.io.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcIoApp'],
    title: 'IR',
    appIcon: 'icon_remotecontrol',
    width: 700,
    height: 500,
    initComponent: function() {
        const me = this;

        me.title += ': ' + me.gos.data.module.name;
    }
});