var traktRoot = '/trakt/';

function traktModal() {
    var myClass = this;
    
    this.openMovie = function(traktId) {
        jQuery.ajax({
            url: traktRoot+'searchByTraktId/movie/',
            data: 'traktId='+traktId,
            type: 'post',
            success: function(html) {
                myClass.open(html);
            }
        });
    }
    this.openShow = function(traktId) {
        jQuery.ajax({
            url: traktRoot+'searchByTraktId/show/',
            data: 'traktId='+traktId,
            type: 'post',
            success: function(html) {
                myClass.open(html);
                myClass.makeSeasonsClickable();
                myClass.makeEpisodesClickable();
            }
        });
    }
    
    this.makeSeasonsClickable = function() {
        jQuery('.trakt-show-seasons .seasonDiv').unbind('click');
        jQuery('.trakt-show-seasons .seasonDiv').click(function(e){
            jQuery(this).parent().find('.episodesDiv').toggle('fast');
        });
    }
    this.makeEpisodesClickable = function() {
        jQuery('.trakt-show-seasons .episodeBlock').unbind('click');
        jQuery('.trakt-show-seasons .episodeBlock').click(function(e){
            var episode = jQuery(this).data('episode');
            var season = jQuery(this).data('season');
            var traktId = jQuery(this).data('trakt');
            myClass.loadEpisodeControls(traktId, season, episode);
        });
    }
    this.loadEpisodeControls = function(traktId, season, episode) {
        jQuery.ajax({
            url: traktRoot+'episodecontrols/',
            type: 'post',
            data: 'season='+season+'&episode='+episode+'&traktId='+traktId,
            success: function(html) {
                jQuery('#controls-loader-'+season).html(html);
            }
        });
    }
    
    this.open = function(content) {
        myClass.close();
        jQuery('body').append(
            '<div class="trakt-modal-bg">'
            +'</div>'
            +'<div class="trakt-modal-detail-wrapper">'
                +'<div class="trakt-modal-detail">'
                    +content
                +'</div>'
            +'</div>'
        );
        jQuery('.trakt-modal-detail-wrapper').click(function(){myClass.close()});
        jQuery('.trakt-modal-detail').click(function(e){e.stopPropagation()});
    }
    
    this.close = function() {
        jQuery('.trakt-modal-detail-wrapper').remove();
        jQuery('.trakt-modal-bg').remove();
    }
    
    this.rateMovie = function(traktId) {
        var rating = jQuery('#modalRating select#rating').val();
        jQuery.ajax({
            url: traktRoot+'sendrating/',
            type: 'POST',
            data: 'traktId='+traktId+'&rating='+rating+'&type=movie',
            success: function(json) {
                myClass.openMovie(traktId);
            }
        });
    }
    this.rateEpisode = function(traktId, season, episode) {
        var rating = jQuery('#controls-loader-'+season+' #modalRating select#rating').val();
        jQuery.ajax({
            url: traktRoot+'sendrating/',
            type: 'POST',
            data: 'traktId='+traktId+'&rating='+rating+'&type=episode&season='+season+'&episode='+episode,
            success: function(json) {
                myClass.loadEpisodeControls(traktId, season, episode);
            }
        });
    }
    this.setMovieAsWatched = function(traktId) {
        var dateVal = jQuery('#modalWatched #date').val();
        var timeVal = jQuery('#modalWatched #time').val();
        jQuery.ajax({
            url: traktRoot+'setaswatched/movie/',
            type: 'POST',
            data: 'traktId='+traktId+'&date='+dateVal+'&time='+timeVal,
            success: function(json) {
                myClass.openMovie(traktId);
            }
        });
    }
    this.setEpisodeAsWatched = function(traktId, season, episode) {
        var dateVal = jQuery('#controls-loader-'+season+' #modalWatched #date').val();
        var timeVal = jQuery('#controls-loader-'+season+' #modalWatched #time').val();
        jQuery.ajax({
            url: traktRoot+'setaswatched/episode/',
            type: 'POST',
            data: 'traktId='+traktId+'&date='+dateVal+'&time='+timeVal+'&season='+season+'&episode='+episode,
            success: function(json) {
                myClass.loadEpisodeControls(traktId, season, episode);
            }
        });
    }
}

function searchField(searchForm) {
    var myClass = this;
    this.form = searchForm;
    this.id = searchForm.data('tableid');
    this.loadUrl = searchForm.data('action');
    
    this.performSearch = function() {
        var searchStr = "searchName="+myClass.getName()+"&searchType="+myClass.getType();
        jQuery.ajax({
            url: myClass.loadUrl,
            data: searchStr, 
            type: 'post',
            success: function(json) {
                var result = '<table><tbody>';
                for (var i = 0; i < json.length; i++) {
                    result += myClass.formatSearchResult(json[i]);
                }    
                result += '</tbody></table>'
                jQuery('#searchResultDiv_'+myClass.id).html(result);
            }
        });
    }
    
    this.formatSearchResult = function(result) {
        return "<tr data-traktid="+result.traktId+">"
                + "<td>"+result.title+"</td>"
                "</tr>";
    }
    this.getName = function() {
        return jQuery('#'+myClass.form.attr('id')+' input[name=searchName]').val();
    }
    this.getType = function() {
        return jQuery('#'+myClass.form.attr('id')+' select[name=searchType]').val();
    }
    
    return this;
}

var searchForms = {};
var tModal;
jQuery(document).ready(function() {
    jQuery('#trakt-search-form').each(function() {
        var id = jQuery(this).data('tableid');
        searchForms[id] = searchField(jQuery(this));
    });
    tModal = new traktModal();
});