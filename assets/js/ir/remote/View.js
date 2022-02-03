Ext.define('GibsonOS.module.hc.ir.remote.View', {
    extend: 'GibsonOS.module.core.component.view.View',
    alias: ['widget.gosModuleHcIrRemoteView'],
    multiSelect: true,
    trackOver: true,
    itemSelector: 'div.hcIrRemoteKey',
    selectedItemCls: 'hcIrRemoteKeySelected',
    overItemCls: 'hcIrRemoteKeyHover',
    remote: {
        name: null,
        itemWidth: 30,
        width: 0,
        height: 0
    },
    gridSize: 10,
    offsetTop: 6,
    offsetLeft: 6,
    initComponent() {
        const me = this;

        me.store = new GibsonOS.module.hc.ir.store.RemoteKey({
            remoteId: me.remote.id
        });
        let id = Ext.id();
        me.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div ',
                    'id="' + id + '{number}" ',
                    'class="hcIrRemoteKey" ',
                    'style="',
                        'left: {left*' + me.gridSize + '+' + me.offsetLeft + '}px; ',
                        'top: {top*' + me.gridSize + '+' + me.offsetTop + '}px; ',
                        'width: {width*' + me.gridSize + '}px; ',
                        'height: {height*' + me.gridSize + '}px; ',
                        '<tpl if="background">background-color: #{background}; </tpl>',
                        '<tpl if="borderTop">border-top: 1px solid #000; </tpl>',
                        '<tpl if="borderRight">border-right: 1px solid #000; </tpl>',
                        '<tpl if="borderBottom">border-bottom: 1px solid #000; </tpl>',
                        '<tpl if="borderLeft">border-left: 1px solid #000; </tpl>',
                        'border-top-left-radius: {borderRadiusTopLeft}%; ',
                        'border-top-right-radius: {borderRadiusTopRight}%; ',
                        'border-bottom-left-radius: {borderRadiusBottomLeft}%; ',
                        'border-bottom-right-radius: {borderRadiusBottomRight}%; ',
                        'padding-top: {height*' + me.gridSize + '/2-9}px',
                        '">',
                    '{name}',
                '</div>',
            '</tpl>'
        );

        me.callParent();
    },
});