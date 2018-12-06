define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/audit/index',
                    edit_url: 'user/audit/edit',
                    del_url: 'user/audit/del_audit',
                    table: 'name_audit',
                }
            });

            var table = $("#table");
             
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        {field: 'id', title: __('Id'),operate: false, sortable: true},
                        {field: 'real_name', title: __('真实姓名'), operate: 'LIKE'},
                        {field: 'id_card', title: __('身份证号'), operate: 'LIKE'},
                        {field: 'status', title: __('状态'), operate: 'LIKE',formatter:function(value){
                        if(value == 1) {
                            return '通过';
                        }
                        else if (value == 0) {
                            return '待审核';
                        }else if(value == 2){
                            return '驳回';
                        }
                        },searchList: {"1": __('通过'), "0": __('待审核'),"2": __('驳回')},
                        },
                        {field: 'sfz_front_img', title: __('身份证正面照'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'sfz_back_img', title: __('身份证背面照'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'add_time', title: __('申请时间'), formatter: Table.api.formatter.datetime, operate: false, addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
                commonSearch: true,
                titleForm: '', //为空则不显示标题，不定义默认显示：普通搜索
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});