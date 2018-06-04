<svg width="960" height="600"></svg>
<script src="https://d3js.org/d3.v4.min.js"></script>
<script>

var svg = d3.select("svg"),
    width = +svg.attr("width"),
    height = +svg.attr("height");

var color = d3.scaleOrdinal(d3.schemeCategory20);

var simulation = d3.forceSimulation()
    .force("link", d3.forceLink().distance(10).strength(0.5))
    .force("charge", d3.forceManyBody())
    .force("center", d3.forceCenter(width / 2, height / 2));

<?php
    $graph = (object)array( 
        'nodes' => array(),
        'links' => array()
    );
/*
    $graph->nodes[] = (object)array( 
        "id" => "Myriel",
        "group" => 1
    );

    $graph->nodes[] = (object)array( 
        "id" => "Napoleon",
        "group" => 1
    );

    $graph->links[] = (object)array(
        "source" => "Napoleon",
        "target" => "Myriel",
        "value" => 1
    );*/

    $query = array(
		'numberposts'   => -1,
		'post_type'     => 'gift',
		'post_status'   => 'publish'
    );
    if( !current_user_can('administrator') ) {
        $current_user = wp_get_current_user();
    }
    $all_gifts = get_posts( $query );
    foreach ($all_gifts as $gift) {
        // sender
        $senderdata = get_userdata($gift->post_author);

        $found = false;
        foreach ($graph->nodes as $node) {
            if ($node->user_id && $node->user_id == $senderdata->ID) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $graph->nodes[] = (object)array( 
                "id" => 'user-'.$senderdata->ID,
                "group" => 1,
                'user_id' => $senderdata->ID,
                'title' => urldecode($senderdata->nickname)
            );
        }

        // recipient
        $recipients = get_field( 'field_58e4f6e88f3d7', $gift->ID );
        unset($recipientdata);
        if ($recipients) {
            foreach ($recipients as $recipient) {
                $recipientdata = get_userdata($recipient['ID']);

                $found = false;
                foreach ($graph->nodes as $node) {
                    if ($node->user_id && $node->user_id == $recipientdata->ID) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $graph->nodes[] = (object)array( 
                        "id" => 'user-'.$recipientdata->ID,
                        "group" => 1,
                        'user_id' => $recipientdata->ID,
                        'title' => urldecode($recipientdata->nickname)
                    );
                }

                $graph->links[] = (object)array(
                    "source" => 'user-'.$senderdata->ID,
                    "target" => 'user-'.$recipientdata->ID,
                    "value" => 1
                );
                break; // only one recipient for now
            }
        }

        /*
        $wraps = get_field( 'field_58e4f5da816ac', $gift->ID);
        unset($object, $venue);
        if ($wraps) {
            foreach ($wraps as $wrap) {
                $object = get_field( 'field_595b4a2bc9c1c', $wrap->ID);
                if (is_array($object) && count($object) > 0) {
                    $object = $object[0];
                } else if (is_a($object, 'WP_Post')) {
                        
                } else {
                    unset($object);
                }  
                if ($object) {
                    
                }
            }
        }*/

    }

?>

var graph = <?php echo json_encode($graph); ?>;

var nodes = graph.nodes,
    nodeById = d3.map(nodes, function(d) { return d.id; }),
    links = graph.links,
    bilinks = [];

links.forEach(function(link) {
var s = link.source = nodeById.get(link.source),
    t = link.target = nodeById.get(link.target),
    i = {}; // intermediate node
nodes.push(i);
links.push({source: s, target: i}, {source: i, target: t});
bilinks.push([s, i, t]);
});

var link = svg.selectAll(".link")
.data(bilinks)
.enter().append("path")
    .attr("class", "link");

var node = svg.selectAll(".node")
.data(nodes.filter(function(d) { return d.id; }))
.enter().append("circle")
    .attr("class", "node")
    .attr("r", 5)
    .attr("fill", function(d) { return color(d.group); })
    .call(d3.drag()
        .on("start", dragstarted)
        .on("drag", dragged)
        .on("end", dragended));

node.append("title")
    .text(function(d) { return d.id; });

simulation
    .nodes(nodes)
    .on("tick", ticked);

simulation.force("link")
    .links(links);

function ticked() {
link.attr("d", positionLink);
node.attr("transform", positionNode);
}

function positionLink(d) {
  return "M" + d[0].x + "," + d[0].y
       + "S" + d[1].x + "," + d[1].y
       + " " + d[2].x + "," + d[2].y;
}

function positionNode(d) {
  return "translate(" + d.x + "," + d.y + ")";
}

function dragstarted(d) {
  if (!d3.event.active) simulation.alphaTarget(0.3).restart();
  d.fx = d.x, d.fy = d.y;
}

function dragged(d) {
  d.fx = d3.event.x, d.fy = d3.event.y;
}

function dragended(d) {
  if (!d3.event.active) simulation.alphaTarget(0);
  d.fx = null, d.fy = null;
}

</script>