from .Match import Match


class FootballMatch(Match):
    """
    Points are distributed based on the goals and the base points.
    Team points = (15 + goals/10 + shots*5 + saves*2) / 2.
    Player points are also calculated.
    """

    def distribute_points(self):
        for team in [self.team1, self.team2]:
            t_goals = 0
            t_shots = 0
            t_saves = 0
            for p in team.players:
                t_goals += getattr(p, 'goals', 0)
                t_shots += getattr(p, 'shots_on_target', 0)
                t_saves += getattr(p, 'saves', 0)
                
                p.points = (
                    getattr(p, 'goals', 0) * 10 +
                    getattr(p, 'assists', 0) * 5 +
                    getattr(p, 'shots_on_target', 0) * 2 +
                    (getattr(p, 'saves', 0) * 2 if p.role == 'Goalkeeper' else 0)
                )
            
            team.points = (15 + t_goals / 10 + t_shots * 5 + t_saves * 2) / 2

    def determine_winner(self):
        t1_goals = sum(getattr(p, 'goals', 0) for p in self.team1.players)
        t2_goals = sum(getattr(p, 'goals', 0) for p in self.team2.players)

        if t1_goals > t2_goals:
            self.winner = self.team1
        elif t2_goals > t1_goals:
            self.winner = self.team2
        else:
            if self.team1.points > self.team2.points:
                self.winner = self.team1
            elif self.team2.points > self.team1.points:
                self.winner = self.team2
            else:
                self.winner = None

    def determine_player_of_the_match(self):
        all_players = self.team1.players + self.team2.players
        self.player_of_the_match = max(all_players, key=lambda p: p.points)
