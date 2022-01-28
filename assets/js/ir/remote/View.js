Ext.define('GibsonOS.module.hc.ir.remote.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcIrRemoteView'],
    multiSelect: false,
    singleSelect: true,
    trackOver: true,
    itemSelector: 'div.hcEthBridgeRemoteItem',
    selectedItemCls: 'hcEthBridgeRemoteItemSelected',
    overItemCls: 'hcEthBridgeRemoteItemHover',
    tpl: new Ext.XTemplate(
        '<tpl for=".">',
        '{[hcEthbridgeTplItem(values, this.remote)]}',
        '</tpl>',
        {
            remote: remote
        }
    ),
});