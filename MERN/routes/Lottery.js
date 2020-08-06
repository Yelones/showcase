const express = require('express');
const router = express.Router();
const LotteryController = require('../controllers/Lottery');

router.get('/', LotteryController.index);
router.post('/add', LotteryController.add);

module.exports = router;