Ext.define('GibsonOS.module.hc.blueprint.View', {
    extend: 'GibsonOS.module.core.component.Panel',
    alias: ['widget.gosModuleHcBlueprintView'],
    requiredPermission: {
        module: 'hc',
        task: 'blueprint'
    },
    cls: 'coloredPanel',
    overflowX: 'auto',
    overflowY: 'auto',
    data: [],
    initComponent() {
        let me = this;

        me.callParent(arguments);

        me.addAction({
            iconCls: 'icon_system system_filter',
            menu: [{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterFrame',
                text: 'Rahmen',
                checkHandler() {
                    me.loadSvg();
                }
            },{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterRoom',
                text: 'Räume',
                checkHandler() {
                    me.loadSvg();
                }
            },{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterFurnishing',
                text: 'Einrichtungen',
                checkHandler() {
                    me.loadSvg();
                }
            },{
                xtype: 'menucheckitem',
                checked: true,
                itemId: 'hcBlueprintViewFilterModule',
                text: 'Module',
                checkHandler() {
                    me.loadSvg();
                }
            },('-'),{
                xtype: 'menucheckitem',
                checked: false,
                itemId: 'hcBlueprintViewFilterDimensions',
                text: 'Mit Dimensionen',
                checkHandler() {
                    me.loadSvg();
                }
            }]
        });

        me.on('render', () => {
            me.loadSvg();
        });
    },
    loadSvg() {
        const me = this;
        let childrenTypes = [];

        me.setLoading(true);

        const addChildrenType = (itemId, key) => {
            if (me.down('#' + itemId).checked) {
                childrenTypes.push(key);
            }
        };
        addChildrenType('hcBlueprintViewFilterFrame', 'FRAME');
        addChildrenType('hcBlueprintViewFilterRoom', 'ROOM');
        addChildrenType('hcBlueprintViewFilterFurnishing', 'FURNISHING');
        addChildrenType('hcBlueprintViewFilterModule', 'MODULE');

        Ext.Ajax.request({
            url: baseDir + 'hc/blueprint/svg',
            method: 'GET',
            params:  {
                id: me.blueprintId,
                'childrenTypes[]': childrenTypes,
                withDimensions: me.down('#hcBlueprintViewFilterDimensions').checked
            },
            success(response) {
                me.update(response.responseText);

                Ext.iterate(document.querySelectorAll('#' + me.id + ' svg *[data-module-id]'), (geometry) => {
                    geometry.onclick = () => {
                        Ext.create('GibsonOS.module.hc.' + geometry.module.helper + '.App', {
                            gos: {
                                data: {
                                    module: geometry.module
                                }
                            }
                        });
                    };
                });

                me.setLoading(false);
            }
        });
    }
});