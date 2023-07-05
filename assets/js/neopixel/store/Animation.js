Ext.define('GibsonOS.module.hc.neopixel.store.Animation', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.gosModuleHcNeopixelAnimationStore'],
    model: 'GibsonOS.module.hc.neopixel.model.animation.Led',
    autoLoad: false,
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixelAnimation',
            method: 'GET'
        };

        me.callParent(arguments);

        return me;
    }
});