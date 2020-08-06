const express = require('express');
const router = express.Router();
const CommentController = require('../controllers/Comment');

router.get('/', CommentController.index);
router.post('/add', CommentController.create);
router.delete('/delete', CommentController.delete);

module.exports = router;