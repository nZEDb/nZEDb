<?php
function smarty_modifier_parray ($string,$explode,$limit=NULL)
{
	if($limit == NULL)
		return explode( $explode , $string );
	else
		return explode( $explode , $string , $limit);
}
?>