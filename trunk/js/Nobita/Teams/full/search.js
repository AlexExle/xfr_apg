!function($, window, document, _undefined)
{
	// Extending to XenForo AutoComplete
	var originalAddValue = XenForo.AutoComplete.prototype.addValue,
		originalShowResults = XenForo.AutoComplete.prototype.showResults;

	XenForo.AutoComplete.prototype.showResults = function(results)
	{
		originalShowResults.call(this, results);

		this.teamList = results.results || {};
	};

	XenForo.AutoComplete.prototype.addValue = function(value)
	{
		originalAddValue.call(this, value);

		var $replace = $(this.$input.data('replace')),
			selectedTeamId = 0;
		if(!$replace.length)
		{
			return;
		}

		if(this.teamList[value])
		{
			selectedTeamId = parseInt(this.teamList[value].team_id) || 0;
		}

		$replace.val(selectedTeamId);
	};
}
(jQuery, this, document);