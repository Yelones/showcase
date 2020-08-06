var express = require('express');
var router = express.Router();
var TodoController = require('../controllers/Todo');

router.get('/', TodoController.index);
router.post('/add', TodoController.add);
router.delete('/delete/:id', TodoController.delete);

module.exports = router;