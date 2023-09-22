Ext.define('GibsonOS.module.hc.blueprint.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcBlueprintPanel'],
    layout: 'border',
    enableContextMenu: true,
    enableKeyEvents: true,
    initComponent() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcBlueprintView',
            region: 'center',
        }];

        me.callParent(arguments);
    }
});