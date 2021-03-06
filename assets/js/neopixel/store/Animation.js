Ext.define('GibsonOS.module.hc.neopixel.store.Animation', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcNeopixelAnimationStore'],
    model: 'GibsonOS.module.hc.neopixel.model.Animation',
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