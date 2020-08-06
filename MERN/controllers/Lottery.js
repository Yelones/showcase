const mongoose = require('mongoose');
const Lottery = require('../models/Lottery');
const moment = require('moment');

class LotteryController {
    index(req, res)
    {
        Lottery.find()
        .sort({date: -1})
        .then((lotteries) => {
            res.json(lotteries);
        });
    }

    add(req, res)
    {
        let numbers = [
            Math.floor(1+ Math.random() * 45),
            Math.floor(1+ Math.random() * 45),
            Math.floor(1+ Math.random() * 45),
            Math.floor(1+ Math.random() * 45),
            Math.floor(1+ Math.random() * 45)
        ];

        Lottery.create({
            date: moment(),
            numbers: numbers
        }, (err, lottery) => {
            if (err) return handleError(err);

            res.json(lottery);
        });
    }
}

module.exports = new LotteryController;