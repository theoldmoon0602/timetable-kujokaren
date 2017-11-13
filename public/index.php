<?php

require_once('../define.php');
require_once('../lib.php');

if (! isset($_GET['token']) || !checkSecureToken($_GET['token'])) {
	echo "出直すデース！　ﾍﾟｯ";
	exit();
}

?>

<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>九条カレンと申すデース！</title>
    <style>
	.container { width: 768px; margin: 0 auto; }
	.list { margin-bottom: 40px; }
	.date,.day { width: 150px; display: inline-block; }
	.message { width: 600px; }
	.row { margin-bottom: 5px; padding-bottom: 2px; }
	input[type=text] { border: 2px solid #999; border-radius: 3px; font-size: 14px; padding: 3px; }
	button { background-color: inherit; border: 2px solid #999; color: #333; padding: 5px 10px; font-weight: bold;}
	button:hover { cursor: pointer; border-color: #fac; }
	button:active { position: relative; top: 1px; cursor: pointer; color: #eee; background-color: #fac; }
    </style>

</head>
<body>
    <div class="container" id="app">
        <header class="header">
            <h1>毎日を豊かにする九条カレンちゃんbot</h1>
        </header>
	<main class="main">
	    <div class="list">
		<h3>一週間の予定デース！！</h3>
		<div class="row" v-for="date in dates">
		    <span class="date">{{ date.date }}</span>
		    <input type="text" class="message" v-model="date.message">
		</div>
	    </div>
	    <div class="list">
		<h3>Template を編集しまショー！！</h3>
		<div class="row" v-for="template in templates">
		    <span class="day">{{ template.day }}</span>
		    <input type="text" class="message" v-model="template.message">
		</div>
	    </div>

	    <div>
		<button v-on:click="save">Save</button>
	    </div>
	</main>
    </div>    
    <script src="https://cdn.jsdelivr.net/npm/vue"></script>
    <script>
	const getData = async function() {
	    return fetch("api.php?token=<?php echo $_GET['token']; ?>")
		.then((res) => res.json());
	}
	const getTemplates = async function() {
	    return fetch("api.php?templates&token=<?php echo $_GET['token']; ?>")
		.then((res) => res.json());
	}
	var app = new Vue({
	    el: '#app',
	    data: {
		dates: [],
		templates: [],
		selected: null,
	    },
	    methods: {
		save: function() {
		    const data = {
			dates: this.dates,
			templates: this.templates
		    };
		    fetch('api.php?save&token=<?php echo $_GET['token']; ?>', { method: 'POST', body: JSON.stringify(data) });
		},
	    },
	    mounted: async function() {
		const templates = getTemplates();
		const data = getData();
		this.dates = await data;
		this.templates = await templates;
	    }
	});
    </script>
</body>
</html>
