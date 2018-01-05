function infiniteScroll(tableObj) {
    var myClass = this;
    this.id = tableObj.attr('id');
    this.htmlTable = tableObj;
    this.pageSize = 15;
    this.page = 0;
    this.hasMore = true;
    this.loadUrl = tableObj.data('loadurl');
    this.loader = '<a id="infiniteScrollTableLoader'+this.id+'">Load More</a>';
    this.sortAttr = null;
    this.sortDir = null;
    this.inputFilters = null;
    
    this.loadMore = function() {
        if (!myClass.hasMore) {
            return;
        }
        jQuery.ajax({
            url: this.loadUrl,
            data: myClass.collectPostParams(),
            type: 'post',
            success: function(json) {
                var tbody = myClass.htmlTable.find('tbody');
                if (json.length < 1) {
                    myClass.hasMore = false;
                    myClass.disableLoader();
                    return;
                }
                jQuery('#infiniteScrollTableLoader'+myClass.id).remove();
                
                jQuery.each(json, function(idx, row) {
                    var rowContent = '';
                    rowContent += '<tr data-row="'+row.id+'">';
                    jQuery.each(row, function(index, column) {
                        rowContent += '<td data-name="'+index+'">'+column+'</td>';
                    });
                    rowContent += '</tr>';
                    tbody.append(rowContent);
                });
                myClass.page++;
                myClass.htmlTable.after(myClass.loader);
                myClass.initLoader();
                myClass.activateLinks();
            }
        });
    }
    
    this.reloadAll = function() {
        myClass.clearContent();
        myClass.loadMore();
    }
    
    this.collectPostParams = function() {
        var returnStr = 'page='+myClass.page+'&pageSize='+myClass.pageSize;
        if (myClass.sortAttr) {
            returnStr += '&sort='+myClass.sortAttr;
        }
        if (myClass.sortDir) {
            returnStr += '&sortDir='+myClass.sortDir;
        }
        myClass.inputFilters = [];
        jQuery('#'+myClass.id+' thead input').each(function(){
            if (jQuery(this).val().length < 1) {
                return;
            }
            myClass.inputFilters.push({"name": jQuery(this).attr('name'), "value": jQuery(this).val()});
        });
        for (var i = 0; i < myClass.inputFilters.length; i++) {
            console.log(myClass.inputFilters);
            returnStr += '&filter_'+myClass.inputFilters[i].name+'='+myClass.inputFilters[i].value;
        }
        
        return returnStr;
    }
    
    this.initLoader = function() {
        jQuery('#infiniteScrollTableLoader'+myClass.id).click(function() {
            myClass.loadMore();
        });
    }
    
    this.activateLinks = function() {
        jQuery('#'+myClass.id+' tbody > tr > td > a').off('click');
        jQuery('#'+myClass.id+' tbody > tr > td > a').click(function() {
            var cell = jQuery(this).parent('td');
            var rowObj = cell.parent('tr')
            var rowId = rowObj.data('row');
            var url = jQuery(this).data('url') + rowId;
            jQuery.ajax({
                url: url,
                type: 'get',
                success: function(json) {
                    if (json.action === "append") {
                        rowObj.after(json.content);
                        myClass.setCloseButtonOnRow(rowId, cell);
                    }
                    if (json.action === "remove") {
                        cell.parent('tr').html(json.success);
                    }
                },
                error: function(xhr, exception) {
                    if (jqXHR.responseJSON.error) {
                        jQuery('#infiniteForm'+formId).before('<div class="deny">'+jqXHR.responseJSON.error+'</div>');
                    }
                }
            });
        });
    }
    
    this.setCloseButtonOnRow = function(rowId, cell) {
        cell.html('<a class="close" data-row="'+rowId+'">Close</a>');
        jQuery('#'+myClass.id+' tr[data-row='+rowId+'] a.close').click(function(){
            var rowId = jQuery(this).data('row');
            jQuery('#'+myClass.id+' tr[data-parent='+rowId+']').remove();
            myClass.reloadRow(rowId);
        });
    }
    
    this.reloadRow = function(id) {
        var url = jQuery('#'+myClass.id).data('loadrowurl')+id;
        jQuery.ajax({
            url: url,
            type: 'get',
            success: function(json) {
                jQuery.each(json, function(idx, row) {
                    var rowContent = '';
                    rowContent += '<tr data-row="'+row.id+'">';
                    jQuery.each(row, function(index, column) {
                        rowContent += '<td data-name="'+index+'">'+column+'</td>';
                    });
                    rowContent += '</tr>';
                    jQuery('#'+myClass.id+' tr[data-row='+id+']').replaceWith(rowContent);
                    myClass.activateLinks();
                });
            }
        });
    }
    
    this.reloadRowWithContent = function(id, content) {
        var url = jQuery('#'+myClass.id).data('loadrowurl')+id;
        jQuery.ajax({
            url: url,
            type: 'get',
            success: function(json) {
                jQuery.each(json, function(idx, row) {
                    var rowContent = '';
                    rowContent += '<tr data-row="'+row.id+'">';
                    jQuery.each(row, function(index, column) {
                        rowContent += '<td data-name="'+index+'">'+column+'</td>';
                    });
                    rowContent += '</tr>';
                    jQuery('#'+myClass.id+' tr[data-row='+id+']').replaceWith(rowContent);
                });
                var cell = jQuery('#'+myClass.id+' tr[data-row='+id+'] td:last');
                myClass.setCloseButtonOnRow(id, cell);
            }
        });
    }
    
    this.disableLoader = function() {
        jQuery('#infiniteScrollTableLoader'+myClass.id)
                .html('Finished Loading')
                .click(function() {});
    }
    
    this.clearContent = function() {
        myClass.htmlTable.find('tbody').html('');
        myClass.page = 0;
        myClass.hasMore = true;
        
    }
    
    this.sendData = function(formId) {
        var url = jQuery('#'+myClass.id).data('updateurl');
        var params = [];
        var paramStr = '';
        jQuery('#infiniteForm'+formId+' input').each(function() {
            params.push(myClass.addFormAttribute(jQuery(this).attr('name'), jQuery(this).val()));
        });
        jQuery('#infiniteForm'+formId+' textarea').each(function() {
            params.push(myClass.addFormAttribute(jQuery(this).attr('name'), jQuery(this).val()));
        });
        jQuery('#infiniteForm'+formId+' select').each(function() {
            params.push(myClass.addFormAttribute(jQuery(this).attr('name'), jQuery(this).val()));
        });
        
        for (var i = 0; i < params.length; i++) {
            if (paramStr !== '') {
                paramStr += '&';
            }
            paramStr += params[i].name+'='+params[i].value;
        }
        
        jQuery.ajax({
            url: url,
            type: 'post',
            data: paramStr,
            success: function(json) {
                if (json.action) {
                    if (json.action === "reload") {
                        myClass.reloadAll();
                    }
                    if (json.action === "replace") {
                        myClass.reloadRowWithContent(formId, '');
                        jQuery('#'+myClass.id+' tr[data-parent='+formId+']').replaceWith(json.content);
                    }
                }
                if (json.success) {
                    jQuery('#infiniteForm'+formId).before('<div class="confirm">'+json.success+'</div>');
                }
            }, 
            error: function (jqXHR, exception) {
                if (jqXHR.responseJSON.error) {
                    jQuery('#infiniteForm'+formId).before('<div class="deny">'+jqXHR.responseJSON.error+'</div>');
                }
            }
        });
    }
    this.addFormAttribute = function(name, value) {
        var item = {
            name: name,
            value: value
        };
        return item;
    }
    
    this.loadMore();
    
    $(window).scroll(function(){
        if (($(window).height() + $(window).scrollTop() + 20) >= $(document).height()) {
            myClass.loadMore();
        }
    });
    
    this.htmlTable.find('thead input').keypress(function(e){
        if (e.which === 13) {
            myClass.clearContent();
            myClass.loadMore();
        }
    });
    
    this.htmlTable.find('.infiniteScrollTableHeaderLink').click(function() {
        myClass.clearContent();
        var link = $(this);
        var newSortVal = link.data('sort');
        if (newSortVal === "ASC") {
            newSortVal = "DESC";
        } else {
            newSortVal = "ASC";
        }
        myClass.sortAttr = link.data('attribute');
        myClass.sortDir = link.data('sort');
        link.data('sort', newSortVal)
        myClass.loadMore();
    });
    
    return this;
}

var infiniteScrolls = {};
jQuery(document).ready(function() {
    jQuery('.infiniteScrollTable').each(function() {
        var id = jQuery(this).data('tableid');
        infiniteScrolls[id] = infiniteScroll(jQuery(this));
    });
});