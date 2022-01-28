Ext.define('GibsonOS.module.hc.ir.remote.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcIrRemoteApp'],
    title: 'Fernbedienung',
    appIcon: 'icon_remotecontrol',
    width: 300,
    height: 600,
    requiredPermission: {
        module: 'hc',
        task: 'ir'
    },
    initComponent() {
        const me = this;

        //me.title += ': ' + me.gos.data.module.name;
        me.items = [];

        me.callParent();
    }
});