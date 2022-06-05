Ext.define('GibsonOS.module.hc.neopixel.store.animation.View', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcNeopixelAnimationViewStore'],
    model: 'GibsonOS.module.hc.neopixel.model.animation.Led',
    autoLoad: false,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixelAnimation/index',
        };

        me.callParent(arguments);

        return me;
    }
});