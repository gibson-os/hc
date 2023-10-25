Ext.define('GibsonOS.module.hc.blueprint.Panel', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcBlueprintPanel'],
    layout: 'border',
    enableToolbar: false,
    initComponent() {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcBlueprintView',
            blueprintId: me.blueprintId,
            region: 'center'
        },{
            xtype: 'gosCoreComponentPanel',
            region: 'south',
            disabled: true,
            flex: 0,
            collapsible: true,
            collapsed: true,
            split: true,
            height: '50%',
            hideCollapseTool: true,
            header: false
        }];

        me.callParent(arguments);
    }
});