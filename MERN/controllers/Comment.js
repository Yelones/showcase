const axios = require('axios');
const moment = require('moment');
const Comment = require('../models/Comment');

class CommentController {
    index(req, res)
    {
        Comment.find()
            .sort({created_at: -1})
            .then(data => res.json(data))
            .catch(err => {
                console.log(err);
                res.json(err);
            });

    }

    create(req, res)
    {
        Comment.create({
            author: req.body.author,
            body: req.body.body,
            created_at: moment()
        })
        .then(data => {
            res.json(data);
        })
        .catch(err => {
            console.log(err);
            res.json(err);
        });
    }

    delete(req, res)
    {
        Comment.findByIdAndDelete(req.body.id)
        .then(data => res.json(data))
        .catch(err => {
            console.log(err);
            res.json(err);
        });
    }
}

module.exports = new CommentController;