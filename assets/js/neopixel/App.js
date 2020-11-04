Ext.define('GibsonOS.module.hc.neopixel.App', {
    extend: 'GibsonOS.module.hc.hcSlave.App',
    alias: ['widget.gosModuleHcNeopixelApp'],
    title: 'Neopixel',
    appIcon: 'icon_led',
    width: 900,
    height: 850,
    requiredPermission: {
        module: 'hc',
        task: 'neopixel'
    },
    initComponent: function () {
        let me = this;

        me.items = [{
            xtype: 'gosModuleHcNeopixelLedPanel',
            title: 'LEDs'
        }];

        me.callParent();

        let viewStore = me.down('gosModuleHcNeopixelLedView').getStore();
        viewStore.getProxy().setExtraParam('moduleId', me.gos.data.module.id);
        viewStore.load();

        /*let imageStore = me.down('#hcNeopixelLedPanelImageLoad').getStore();
        imageStore.getProxy().setExtraParam('moduleId', me.gos.data.module.id);
        imageStore.load();*/

        /*let animationsStore = me.down('gosModuleHcNeopixelAnimationsStore');
        animationsStore.getProxy().setExtraParam('moduleId', me.gos.data.module.id);
        animationsStore.load();

        let animationStore = me.down('gosModuleHcNeopixelAnimationStore');
        animationStore.getProxy().setExtraParam('moduleId', me.gos.data.module.id);
        animationStore.load();*/
    }
});