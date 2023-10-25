Ext.define('GibsonOS.module.hc.blueprint.App', {
    extend: 'GibsonOS.App',
    alias: ['widget.gosModuleHcBlueprintApp'],
    title: 'Blueprint',
    appIcon: 'icon_homecontrol',
    width: 900,
    height: 600,
    requiredPermission: {
        module: 'hc',
        task: 'blueprint'
    },
    initComponent(arguments) {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcBlueprintPanel',
            blueprintId: me.blueprintId
        }];

        me.callParent(arguments);
    }
});