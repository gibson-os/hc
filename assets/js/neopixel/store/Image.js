Ext.define('GibsonOS.module.hc.neopixel.store.Image', {
    extend: 'GibsonOS.data.Store',
    alias: ['store.hcNeopixelImageStore'],
    autoLoad: true,
    model: 'GibsonOS.module.hc.neopixel.model.Image',
    constructor: function(data) {
        let me = this;

        me.proxy = {
            type: 'gosDataProxyAjax',
            url: baseDir + 'hc/neopixel/images',
            extraParams: {
                moduleId: data.gos.data.module.id
            }
        };

        me.callParent(arguments);

        return me;
    }
});